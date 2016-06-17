/**
 * QueryObjects.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class QueryObjects  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params;

    private org.apache.axis.types.UnsignedInt firstEntry;

    private org.apache.axis.types.UnsignedInt maxEntries;

    private java.lang.Boolean hierarchical;

    private com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] order;

    private java.lang.String[] minimalProps;

    private java.lang.String[] requestProps;

    private com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas;

    private java.lang.Boolean getObjectMode;

    public QueryObjects() {
    }

    public QueryObjects(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params,
           org.apache.axis.types.UnsignedInt firstEntry,
           org.apache.axis.types.UnsignedInt maxEntries,
           java.lang.Boolean hierarchical,
           com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] order,
           java.lang.String[] minimalProps,
           java.lang.String[] requestProps,
           com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas,
           java.lang.Boolean getObjectMode) {
           this.ticket = ticket;
           this.params = params;
           this.firstEntry = firstEntry;
           this.maxEntries = maxEntries;
           this.hierarchical = hierarchical;
           this.order = order;
           this.minimalProps = minimalProps;
           this.requestProps = requestProps;
           this.areas = areas;
           this.getObjectMode = getObjectMode;
    }


    /**
     * Gets the ticket value for this QueryObjects.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this QueryObjects.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the params value for this QueryObjects.
     * 
     * @return params
     */
    public com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] getParams() {
        return params;
    }


    /**
     * Sets the params value for this QueryObjects.
     * 
     * @param params
     */
    public void setParams(com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params) {
        this.params = params;
    }


    /**
     * Gets the firstEntry value for this QueryObjects.
     * 
     * @return firstEntry
     */
    public org.apache.axis.types.UnsignedInt getFirstEntry() {
        return firstEntry;
    }


    /**
     * Sets the firstEntry value for this QueryObjects.
     * 
     * @param firstEntry
     */
    public void setFirstEntry(org.apache.axis.types.UnsignedInt firstEntry) {
        this.firstEntry = firstEntry;
    }


    /**
     * Gets the maxEntries value for this QueryObjects.
     * 
     * @return maxEntries
     */
    public org.apache.axis.types.UnsignedInt getMaxEntries() {
        return maxEntries;
    }


    /**
     * Sets the maxEntries value for this QueryObjects.
     * 
     * @param maxEntries
     */
    public void setMaxEntries(org.apache.axis.types.UnsignedInt maxEntries) {
        this.maxEntries = maxEntries;
    }


    /**
     * Gets the hierarchical value for this QueryObjects.
     * 
     * @return hierarchical
     */
    public java.lang.Boolean getHierarchical() {
        return hierarchical;
    }


    /**
     * Sets the hierarchical value for this QueryObjects.
     * 
     * @param hierarchical
     */
    public void setHierarchical(java.lang.Boolean hierarchical) {
        this.hierarchical = hierarchical;
    }


    /**
     * Gets the order value for this QueryObjects.
     * 
     * @return order
     */
    public com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] getOrder() {
        return order;
    }


    /**
     * Sets the order value for this QueryObjects.
     * 
     * @param order
     */
    public void setOrder(com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] order) {
        this.order = order;
    }


    /**
     * Gets the minimalProps value for this QueryObjects.
     * 
     * @return minimalProps
     */
    public java.lang.String[] getMinimalProps() {
        return minimalProps;
    }


    /**
     * Sets the minimalProps value for this QueryObjects.
     * 
     * @param minimalProps
     */
    public void setMinimalProps(java.lang.String[] minimalProps) {
        this.minimalProps = minimalProps;
    }


    /**
     * Gets the requestProps value for this QueryObjects.
     * 
     * @return requestProps
     */
    public java.lang.String[] getRequestProps() {
        return requestProps;
    }


    /**
     * Sets the requestProps value for this QueryObjects.
     * 
     * @param requestProps
     */
    public void setRequestProps(java.lang.String[] requestProps) {
        this.requestProps = requestProps;
    }


    /**
     * Gets the areas value for this QueryObjects.
     * 
     * @return areas
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AreaType[] getAreas() {
        return areas;
    }


    /**
     * Sets the areas value for this QueryObjects.
     * 
     * @param areas
     */
    public void setAreas(com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
        this.areas = areas;
    }


    /**
     * Gets the getObjectMode value for this QueryObjects.
     * 
     * @return getObjectMode
     */
    public java.lang.Boolean getGetObjectMode() {
        return getObjectMode;
    }


    /**
     * Sets the getObjectMode value for this QueryObjects.
     * 
     * @param getObjectMode
     */
    public void setGetObjectMode(java.lang.Boolean getObjectMode) {
        this.getObjectMode = getObjectMode;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof QueryObjects)) return false;
        QueryObjects other = (QueryObjects) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.params==null && other.getParams()==null) || 
             (this.params!=null &&
              java.util.Arrays.equals(this.params, other.getParams()))) &&
            ((this.firstEntry==null && other.getFirstEntry()==null) || 
             (this.firstEntry!=null &&
              this.firstEntry.equals(other.getFirstEntry()))) &&
            ((this.maxEntries==null && other.getMaxEntries()==null) || 
             (this.maxEntries!=null &&
              this.maxEntries.equals(other.getMaxEntries()))) &&
            ((this.hierarchical==null && other.getHierarchical()==null) || 
             (this.hierarchical!=null &&
              this.hierarchical.equals(other.getHierarchical()))) &&
            ((this.order==null && other.getOrder()==null) || 
             (this.order!=null &&
              java.util.Arrays.equals(this.order, other.getOrder()))) &&
            ((this.minimalProps==null && other.getMinimalProps()==null) || 
             (this.minimalProps!=null &&
              java.util.Arrays.equals(this.minimalProps, other.getMinimalProps()))) &&
            ((this.requestProps==null && other.getRequestProps()==null) || 
             (this.requestProps!=null &&
              java.util.Arrays.equals(this.requestProps, other.getRequestProps()))) &&
            ((this.areas==null && other.getAreas()==null) || 
             (this.areas!=null &&
              java.util.Arrays.equals(this.areas, other.getAreas()))) &&
            ((this.getObjectMode==null && other.getGetObjectMode()==null) || 
             (this.getObjectMode!=null &&
              this.getObjectMode.equals(other.getGetObjectMode())));
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
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getParams() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getParams());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getParams(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFirstEntry() != null) {
            _hashCode += getFirstEntry().hashCode();
        }
        if (getMaxEntries() != null) {
            _hashCode += getMaxEntries().hashCode();
        }
        if (getHierarchical() != null) {
            _hashCode += getHierarchical().hashCode();
        }
        if (getOrder() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getOrder());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getOrder(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMinimalProps() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMinimalProps());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMinimalProps(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRequestProps() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestProps());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestProps(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getAreas() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAreas());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAreas(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getGetObjectMode() != null) {
            _hashCode += getGetObjectMode().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(QueryObjects.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">QueryObjects"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("params");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Params"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "QueryParam"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "QueryParam"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("firstEntry");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FirstEntry"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("maxEntries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MaxEntries"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("hierarchical");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Hierarchical"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("order");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Order"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "QueryOrder"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "QueryOrder"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("minimalProps");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MinimalProps"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestProps");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestProps"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("areas");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Areas"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AreaType"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AreaType"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("getObjectMode");
        elemField.setXmlName(new javax.xml.namespace.QName("", "GetObjectMode"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
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
