/**
 * AccessProfile.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class AccessProfile  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private java.math.BigInteger sortOrder;

    private java.lang.String description;

    private com.woodwing.enterprise.interfaces.services.adm.ProfileFeature[] profileFeatures;

    public AccessProfile() {
    }

    public AccessProfile(
           java.math.BigInteger id,
           java.lang.String name,
           java.math.BigInteger sortOrder,
           java.lang.String description,
           com.woodwing.enterprise.interfaces.services.adm.ProfileFeature[] profileFeatures) {
           this.id = id;
           this.name = name;
           this.sortOrder = sortOrder;
           this.description = description;
           this.profileFeatures = profileFeatures;
    }


    /**
     * Gets the id value for this AccessProfile.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this AccessProfile.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this AccessProfile.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this AccessProfile.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the sortOrder value for this AccessProfile.
     * 
     * @return sortOrder
     */
    public java.math.BigInteger getSortOrder() {
        return sortOrder;
    }


    /**
     * Sets the sortOrder value for this AccessProfile.
     * 
     * @param sortOrder
     */
    public void setSortOrder(java.math.BigInteger sortOrder) {
        this.sortOrder = sortOrder;
    }


    /**
     * Gets the description value for this AccessProfile.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this AccessProfile.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the profileFeatures value for this AccessProfile.
     * 
     * @return profileFeatures
     */
    public com.woodwing.enterprise.interfaces.services.adm.ProfileFeature[] getProfileFeatures() {
        return profileFeatures;
    }


    /**
     * Sets the profileFeatures value for this AccessProfile.
     * 
     * @param profileFeatures
     */
    public void setProfileFeatures(com.woodwing.enterprise.interfaces.services.adm.ProfileFeature[] profileFeatures) {
        this.profileFeatures = profileFeatures;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof AccessProfile)) return false;
        AccessProfile other = (AccessProfile) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.sortOrder==null && other.getSortOrder()==null) || 
             (this.sortOrder!=null &&
              this.sortOrder.equals(other.getSortOrder()))) &&
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.profileFeatures==null && other.getProfileFeatures()==null) || 
             (this.profileFeatures!=null &&
              java.util.Arrays.equals(this.profileFeatures, other.getProfileFeatures())));
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
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getSortOrder() != null) {
            _hashCode += getSortOrder().hashCode();
        }
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getProfileFeatures() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getProfileFeatures());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getProfileFeatures(), i);
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
        new org.apache.axis.description.TypeDesc(AccessProfile.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "AccessProfile"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sortOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SortOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("profileFeatures");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ProfileFeatures"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ProfileFeature"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ProfileFeature"));
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
