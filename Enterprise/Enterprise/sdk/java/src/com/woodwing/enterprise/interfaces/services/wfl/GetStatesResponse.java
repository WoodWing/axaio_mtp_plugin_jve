/**
 * GetStatesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetStatesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.State[] states;

    private com.woodwing.enterprise.interfaces.services.wfl.User[] routeToUsers;

    private com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] routeToGroups;

    public GetStatesResponse() {
    }

    public GetStatesResponse(
           com.woodwing.enterprise.interfaces.services.wfl.State[] states,
           com.woodwing.enterprise.interfaces.services.wfl.User[] routeToUsers,
           com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] routeToGroups) {
           this.states = states;
           this.routeToUsers = routeToUsers;
           this.routeToGroups = routeToGroups;
    }


    /**
     * Gets the states value for this GetStatesResponse.
     * 
     * @return states
     */
    public com.woodwing.enterprise.interfaces.services.wfl.State[] getStates() {
        return states;
    }


    /**
     * Sets the states value for this GetStatesResponse.
     * 
     * @param states
     */
    public void setStates(com.woodwing.enterprise.interfaces.services.wfl.State[] states) {
        this.states = states;
    }


    /**
     * Gets the routeToUsers value for this GetStatesResponse.
     * 
     * @return routeToUsers
     */
    public com.woodwing.enterprise.interfaces.services.wfl.User[] getRouteToUsers() {
        return routeToUsers;
    }


    /**
     * Sets the routeToUsers value for this GetStatesResponse.
     * 
     * @param routeToUsers
     */
    public void setRouteToUsers(com.woodwing.enterprise.interfaces.services.wfl.User[] routeToUsers) {
        this.routeToUsers = routeToUsers;
    }


    /**
     * Gets the routeToGroups value for this GetStatesResponse.
     * 
     * @return routeToGroups
     */
    public com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] getRouteToGroups() {
        return routeToGroups;
    }


    /**
     * Sets the routeToGroups value for this GetStatesResponse.
     * 
     * @param routeToGroups
     */
    public void setRouteToGroups(com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] routeToGroups) {
        this.routeToGroups = routeToGroups;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetStatesResponse)) return false;
        GetStatesResponse other = (GetStatesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.states==null && other.getStates()==null) || 
             (this.states!=null &&
              java.util.Arrays.equals(this.states, other.getStates()))) &&
            ((this.routeToUsers==null && other.getRouteToUsers()==null) || 
             (this.routeToUsers!=null &&
              java.util.Arrays.equals(this.routeToUsers, other.getRouteToUsers()))) &&
            ((this.routeToGroups==null && other.getRouteToGroups()==null) || 
             (this.routeToGroups!=null &&
              java.util.Arrays.equals(this.routeToGroups, other.getRouteToGroups())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getStates() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStates());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStates(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRouteToUsers() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRouteToUsers());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRouteToUsers(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRouteToGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRouteToGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRouteToGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetStatesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "GetStatesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("states");
        elemField.setXmlName(new javax.xml.namespace.QName("", "States"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "State"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "State"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routeToUsers");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RouteToUsers"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "User"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "User"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routeToGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RouteToGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "UserGroup"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UserGroup"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
