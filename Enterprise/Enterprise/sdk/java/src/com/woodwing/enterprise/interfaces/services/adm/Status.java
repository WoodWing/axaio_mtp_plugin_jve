/**
 * Status.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class Status  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private org.apache.axis.types.UnsignedInt sortOrder;

    private com.woodwing.enterprise.interfaces.services.adm.ObjectType type;

    private java.lang.Boolean produce;

    private java.lang.String color;

    private java.lang.Integer deadlineRelative;

    private com.woodwing.enterprise.interfaces.services.adm.IdName nextStatus;

    private java.lang.Boolean createPermanentVersion;

    private java.lang.Boolean removeIntermediateVersions;

    private java.lang.Boolean automaticallySendToNext;

    private java.lang.Boolean readyForPublishing;

    private com.woodwing.enterprise.interfaces.services.adm.StatusPhase phase;

    private java.lang.Boolean skipIdsa;

    public Status() {
    }

    public Status(
           java.math.BigInteger id,
           java.lang.String name,
           org.apache.axis.types.UnsignedInt sortOrder,
           com.woodwing.enterprise.interfaces.services.adm.ObjectType type,
           java.lang.Boolean produce,
           java.lang.String color,
           java.lang.Integer deadlineRelative,
           com.woodwing.enterprise.interfaces.services.adm.IdName nextStatus,
           java.lang.Boolean createPermanentVersion,
           java.lang.Boolean removeIntermediateVersions,
           java.lang.Boolean automaticallySendToNext,
           java.lang.Boolean readyForPublishing,
           com.woodwing.enterprise.interfaces.services.adm.StatusPhase phase,
           java.lang.Boolean skipIdsa) {
           this.id = id;
           this.name = name;
           this.sortOrder = sortOrder;
           this.type = type;
           this.produce = produce;
           this.color = color;
           this.deadlineRelative = deadlineRelative;
           this.nextStatus = nextStatus;
           this.createPermanentVersion = createPermanentVersion;
           this.removeIntermediateVersions = removeIntermediateVersions;
           this.automaticallySendToNext = automaticallySendToNext;
           this.readyForPublishing = readyForPublishing;
           this.phase = phase;
           this.skipIdsa = skipIdsa;
    }


    /**
     * Gets the id value for this Status.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this Status.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this Status.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Status.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the sortOrder value for this Status.
     * 
     * @return sortOrder
     */
    public org.apache.axis.types.UnsignedInt getSortOrder() {
        return sortOrder;
    }


    /**
     * Sets the sortOrder value for this Status.
     * 
     * @param sortOrder
     */
    public void setSortOrder(org.apache.axis.types.UnsignedInt sortOrder) {
        this.sortOrder = sortOrder;
    }


    /**
     * Gets the type value for this Status.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.adm.ObjectType getType() {
        return type;
    }


    /**
     * Sets the type value for this Status.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.adm.ObjectType type) {
        this.type = type;
    }


    /**
     * Gets the produce value for this Status.
     * 
     * @return produce
     */
    public java.lang.Boolean getProduce() {
        return produce;
    }


    /**
     * Sets the produce value for this Status.
     * 
     * @param produce
     */
    public void setProduce(java.lang.Boolean produce) {
        this.produce = produce;
    }


    /**
     * Gets the color value for this Status.
     * 
     * @return color
     */
    public java.lang.String getColor() {
        return color;
    }


    /**
     * Sets the color value for this Status.
     * 
     * @param color
     */
    public void setColor(java.lang.String color) {
        this.color = color;
    }


    /**
     * Gets the deadlineRelative value for this Status.
     * 
     * @return deadlineRelative
     */
    public java.lang.Integer getDeadlineRelative() {
        return deadlineRelative;
    }


    /**
     * Sets the deadlineRelative value for this Status.
     * 
     * @param deadlineRelative
     */
    public void setDeadlineRelative(java.lang.Integer deadlineRelative) {
        this.deadlineRelative = deadlineRelative;
    }


    /**
     * Gets the nextStatus value for this Status.
     * 
     * @return nextStatus
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName getNextStatus() {
        return nextStatus;
    }


    /**
     * Sets the nextStatus value for this Status.
     * 
     * @param nextStatus
     */
    public void setNextStatus(com.woodwing.enterprise.interfaces.services.adm.IdName nextStatus) {
        this.nextStatus = nextStatus;
    }


    /**
     * Gets the createPermanentVersion value for this Status.
     * 
     * @return createPermanentVersion
     */
    public java.lang.Boolean getCreatePermanentVersion() {
        return createPermanentVersion;
    }


    /**
     * Sets the createPermanentVersion value for this Status.
     * 
     * @param createPermanentVersion
     */
    public void setCreatePermanentVersion(java.lang.Boolean createPermanentVersion) {
        this.createPermanentVersion = createPermanentVersion;
    }


    /**
     * Gets the removeIntermediateVersions value for this Status.
     * 
     * @return removeIntermediateVersions
     */
    public java.lang.Boolean getRemoveIntermediateVersions() {
        return removeIntermediateVersions;
    }


    /**
     * Sets the removeIntermediateVersions value for this Status.
     * 
     * @param removeIntermediateVersions
     */
    public void setRemoveIntermediateVersions(java.lang.Boolean removeIntermediateVersions) {
        this.removeIntermediateVersions = removeIntermediateVersions;
    }


    /**
     * Gets the automaticallySendToNext value for this Status.
     * 
     * @return automaticallySendToNext
     */
    public java.lang.Boolean getAutomaticallySendToNext() {
        return automaticallySendToNext;
    }


    /**
     * Sets the automaticallySendToNext value for this Status.
     * 
     * @param automaticallySendToNext
     */
    public void setAutomaticallySendToNext(java.lang.Boolean automaticallySendToNext) {
        this.automaticallySendToNext = automaticallySendToNext;
    }


    /**
     * Gets the readyForPublishing value for this Status.
     * 
     * @return readyForPublishing
     */
    public java.lang.Boolean getReadyForPublishing() {
        return readyForPublishing;
    }


    /**
     * Sets the readyForPublishing value for this Status.
     * 
     * @param readyForPublishing
     */
    public void setReadyForPublishing(java.lang.Boolean readyForPublishing) {
        this.readyForPublishing = readyForPublishing;
    }


    /**
     * Gets the phase value for this Status.
     * 
     * @return phase
     */
    public com.woodwing.enterprise.interfaces.services.adm.StatusPhase getPhase() {
        return phase;
    }


    /**
     * Sets the phase value for this Status.
     * 
     * @param phase
     */
    public void setPhase(com.woodwing.enterprise.interfaces.services.adm.StatusPhase phase) {
        this.phase = phase;
    }


    /**
     * Gets the skipIdsa value for this Status.
     * 
     * @return skipIdsa
     */
    public java.lang.Boolean getSkipIdsa() {
        return skipIdsa;
    }


    /**
     * Sets the skipIdsa value for this Status.
     * 
     * @param skipIdsa
     */
    public void setSkipIdsa(java.lang.Boolean skipIdsa) {
        this.skipIdsa = skipIdsa;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Status)) return false;
        Status other = (Status) obj;
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
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.produce==null && other.getProduce()==null) || 
             (this.produce!=null &&
              this.produce.equals(other.getProduce()))) &&
            ((this.color==null && other.getColor()==null) || 
             (this.color!=null &&
              this.color.equals(other.getColor()))) &&
            ((this.deadlineRelative==null && other.getDeadlineRelative()==null) || 
             (this.deadlineRelative!=null &&
              this.deadlineRelative.equals(other.getDeadlineRelative()))) &&
            ((this.nextStatus==null && other.getNextStatus()==null) || 
             (this.nextStatus!=null &&
              this.nextStatus.equals(other.getNextStatus()))) &&
            ((this.createPermanentVersion==null && other.getCreatePermanentVersion()==null) || 
             (this.createPermanentVersion!=null &&
              this.createPermanentVersion.equals(other.getCreatePermanentVersion()))) &&
            ((this.removeIntermediateVersions==null && other.getRemoveIntermediateVersions()==null) || 
             (this.removeIntermediateVersions!=null &&
              this.removeIntermediateVersions.equals(other.getRemoveIntermediateVersions()))) &&
            ((this.automaticallySendToNext==null && other.getAutomaticallySendToNext()==null) || 
             (this.automaticallySendToNext!=null &&
              this.automaticallySendToNext.equals(other.getAutomaticallySendToNext()))) &&
            ((this.readyForPublishing==null && other.getReadyForPublishing()==null) || 
             (this.readyForPublishing!=null &&
              this.readyForPublishing.equals(other.getReadyForPublishing()))) &&
            ((this.phase==null && other.getPhase()==null) || 
             (this.phase!=null &&
              this.phase.equals(other.getPhase()))) &&
            ((this.skipIdsa==null && other.getSkipIdsa()==null) || 
             (this.skipIdsa!=null &&
              this.skipIdsa.equals(other.getSkipIdsa())));
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
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getProduce() != null) {
            _hashCode += getProduce().hashCode();
        }
        if (getColor() != null) {
            _hashCode += getColor().hashCode();
        }
        if (getDeadlineRelative() != null) {
            _hashCode += getDeadlineRelative().hashCode();
        }
        if (getNextStatus() != null) {
            _hashCode += getNextStatus().hashCode();
        }
        if (getCreatePermanentVersion() != null) {
            _hashCode += getCreatePermanentVersion().hashCode();
        }
        if (getRemoveIntermediateVersions() != null) {
            _hashCode += getRemoveIntermediateVersions().hashCode();
        }
        if (getAutomaticallySendToNext() != null) {
            _hashCode += getAutomaticallySendToNext().hashCode();
        }
        if (getReadyForPublishing() != null) {
            _hashCode += getReadyForPublishing().hashCode();
        }
        if (getPhase() != null) {
            _hashCode += getPhase().hashCode();
        }
        if (getSkipIdsa() != null) {
            _hashCode += getSkipIdsa().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Status.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Status"));
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
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ObjectType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("produce");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Produce"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("color");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Color"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deadlineRelative");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DeadlineRelative"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("nextStatus");
        elemField.setXmlName(new javax.xml.namespace.QName("", "NextStatus"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("createPermanentVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CreatePermanentVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("removeIntermediateVersions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RemoveIntermediateVersions"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("automaticallySendToNext");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AutomaticallySendToNext"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("readyForPublishing");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReadyForPublishing"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("phase");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Phase"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "StatusPhase"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("skipIdsa");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SkipIdsa"));
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
